<?php

namespace App\Enums;

/**
 * Undergraduate degree programs offered by the University of Mindanao.
 *
 * Values are the official program names as published by the university, so the
 * string stored on a student profile reads correctly on transcripts and
 * certificates without any further mapping.
 *
 * @see https://umindanao.edu.ph/colleges/main
 */
enum Program: string
{
    // College of Accounting Education
    case Accountancy = 'Bachelor of Science in Accountancy';
    case AccountingInformationSystem = 'Bachelor of Science in Accounting Information System';
    case ManagementAccounting = 'Bachelor of Science in Management Accounting';

    // College of Architecture and Fine Arts Education
    case Architecture = 'Bachelor of Science in Architecture';
    case FineArtsPainting = 'Bachelor of Fine Arts and Design Major in Painting';
    case InteriorDesign = 'Bachelor of Science in Interior Design';

    // College of Arts and Sciences Education
    case Communication = 'Bachelor of Arts in Communication';
    case EnglishLanguage = 'Bachelor of Arts in English Language';
    case PoliticalScience = 'Bachelor of Arts in Political Science';
    case Agroforestry = 'Bachelor of Science in Agroforestry';
    case Biology = 'Bachelor of Science in Biology with Specializations in Ecology';
    case EnvironmentalScience = 'Bachelor of Science in Environmental Science';
    case Forestry = 'Bachelor of Science in Forestry';
    case Psychology = 'Bachelor of Science in Psychology';
    case SocialWork = 'Bachelor of Science in Social Work';

    // College of Business Administration Education
    case BusinessEconomics = 'Bachelor of Science in Business Administration Major in Business Economics';
    case FinancialManagement = 'Bachelor of Science in Business Administration Major in Financial Management';
    case HumanResourceManagement = 'Bachelor of Science in Business Administration Major in Human Resource Management';
    case MarketingManagement = 'Bachelor of Science in Business Administration Major in Marketing Management';
    case CustomsAdministration = 'Bachelor of Science in Customs Administration';
    case Entrepreneurship = 'Bachelor of Science in Entrepreneurship';
    case LegalManagement = 'Bachelor of Science in Legal Management';
    case RealEstateManagement = 'Bachelor of Science in Real Estate Management';

    // College of Computing Education
    case ComputerScience = 'Bachelor of Science in Computer Science';
    case GameDevelopment = 'Bachelor of Science in Entertainment and Multimedia Computing Major in Game Development';
    case InformationTechnology = 'Bachelor of Science in Information Technology';
    case LibraryAndInformationScience = 'Bachelor of Library and Information Science';
    case MultimediaArts = 'Bachelor of Multimedia Arts';

    // College of Criminal Justice Education
    case Criminology = 'Bachelor of Science in Criminology';

    // College of Engineering Education
    case ChemicalEngineering = 'Bachelor of Science in Chemical Engineering';
    case CivilEngineeringGeotechnical = 'Bachelor of Science in Civil Engineering Major in Geotechnical';
    case CivilEngineeringStructural = 'Bachelor of Science in Civil Engineering Major in Structural';
    case CivilEngineeringTransportation = 'Bachelor of Science in Civil Engineering Major in Transportation';
    case ComputerEngineering = 'Bachelor of Science in Computer Engineering';
    case ElectricalEngineering = 'Bachelor of Science in Electrical Engineering';
    case ElectronicsEngineering = 'Bachelor of Science in Electronics Engineering';
    case MaterialsEngineering = 'Bachelor of Science in Materials Engineering';
    case MechanicalEngineering = 'Bachelor of Science in Mechanical Engineering';

    // College of Health Sciences Education
    case MedicalTechnology = 'Bachelor of Science in Medical Technology';
    case Nursing = 'Bachelor of Science in Nursing';
    case NutritionAndDietetics = 'Bachelor of Science in Nutrition and Dietetics';
    case Pharmacy = 'Bachelor of Science in Pharmacy';

    // College of Hospitality Education
    case HospitalityManagement = 'Bachelor of Science in Hospitality Management';
    case TourismManagement = 'Bachelor of Science in Tourism Management';

    // College of Teacher Education
    case ElementaryEducation = 'Bachelor of Elementary Education';
    case PhysicalEducation = 'Bachelor of Physical Education';
    case SecondaryEducationEnglish = 'Bachelor of Secondary Education Major in English';
    case SecondaryEducationFilipino = 'Bachelor of Secondary Education Major in Filipino';
    case SecondaryEducationMathematics = 'Bachelor of Secondary Education Major in Mathematics';
    case SecondaryEducationScience = 'Bachelor of Secondary Education Major in Science';
    case SecondaryEducationSocialStudies = 'Bachelor of Secondary Education Major in Social Studies';
    case SpecialNeedsEducation = 'Bachelor of Special Needs Education Major in Elementary School Teaching';

    /**
     * The college that offers this program, used to group the picker.
     */
    public function college(): string
    {
        return match ($this) {
            self::Accountancy,
            self::AccountingInformationSystem,
            self::ManagementAccounting => 'College of Accounting Education',

            self::Architecture,
            self::FineArtsPainting,
            self::InteriorDesign => 'College of Architecture and Fine Arts Education',

            self::Communication,
            self::EnglishLanguage,
            self::PoliticalScience,
            self::Agroforestry,
            self::Biology,
            self::EnvironmentalScience,
            self::Forestry,
            self::Psychology,
            self::SocialWork => 'College of Arts and Sciences Education',

            self::BusinessEconomics,
            self::FinancialManagement,
            self::HumanResourceManagement,
            self::MarketingManagement,
            self::CustomsAdministration,
            self::Entrepreneurship,
            self::LegalManagement,
            self::RealEstateManagement => 'College of Business Administration Education',

            self::ComputerScience,
            self::GameDevelopment,
            self::InformationTechnology,
            self::LibraryAndInformationScience,
            self::MultimediaArts => 'College of Computing Education',

            self::Criminology => 'College of Criminal Justice Education',

            self::ChemicalEngineering,
            self::CivilEngineeringGeotechnical,
            self::CivilEngineeringStructural,
            self::CivilEngineeringTransportation,
            self::ComputerEngineering,
            self::ElectricalEngineering,
            self::ElectronicsEngineering,
            self::MaterialsEngineering,
            self::MechanicalEngineering => 'College of Engineering Education',

            self::MedicalTechnology,
            self::Nursing,
            self::NutritionAndDietetics,
            self::Pharmacy => 'College of Health Sciences Education',

            self::HospitalityManagement,
            self::TourismManagement => 'College of Hospitality Education',

            self::ElementaryEducation,
            self::PhysicalEducation,
            self::SecondaryEducationEnglish,
            self::SecondaryEducationFilipino,
            self::SecondaryEducationMathematics,
            self::SecondaryEducationScience,
            self::SecondaryEducationSocialStudies,
            self::SpecialNeedsEducation => 'College of Teacher Education',
        };
    }

    /**
     * Every program grouped by the college that offers it, for the picker.
     *
     * @return array<string, array<int, self>>
     */
    public static function groupedByCollege(): array
    {
        $grouped = [];

        foreach (self::cases() as $program) {
            $grouped[$program->college()][] = $program;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
